package main

import (
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"
)

const (
	DownloadURL = "https://remoto.asistenciavircom.com/rustdesk-host%3D191.101.233.82%2Ckey%3DnWZn0wE7Gq6meimntlv0G8usBkxDjoR0%2BOTgUh76WEU%3D.exe"
	FileName    = "vircom_client.exe"
	Password    = "Vircom2025!"
)

func main() {
	// Enable UTF-8 for console (best effort)
	exec.Command("chcp", "65001").Run()

	fmt.Println("\n==========================================")
	fmt.Println("      🚀 SOPORTE REMOTO VIRCOM")
	fmt.Println("==========================================")
	fmt.Println("")

	// 1. Download
	fmt.Println("[1/4] Descargando cliente optimizado...")
	if err := downloadFile(FileName, DownloadURL); err != nil {
		fmt.Printf("Error descargando: %v\n", err)
		pause()
		return
	}

	// 2. Install Service
	fmt.Println("[2/4] Instalando servicio (puede tardar unos segundos)...")
	// Execute silent install
	path, _ := filepath.Abs(FileName)
	cmd := exec.Command(path, "--silent-install")
	if err := cmd.Run(); err != nil {
		fmt.Printf("Nota: La instalación silenciosa reportó: %v (esto es normal si ya estaba instalado)\n", err)
	}
	time.Sleep(5 * time.Second)

	// 3. Configure Password (Firewall rules usually handled by installer or we can add them)
	fmt.Println("[3/4] Configurando acceso seguro...")
	rustDeskPath := findRustDesk()
	if rustDeskPath == "" {
		fmt.Println("Error: No se encontró la instalación de RustDesk.")
		pause()
		return
	}

	// Stop service, Set password, Start service (Best practice for key reload)
	exec.Command("net", "stop", "rustdesk").Run()
	
	passCmd := exec.Command(rustDeskPath, "--password", Password)
	if err := passCmd.Run(); err != nil {
		fmt.Printf("Error estableciendo contraseña: %v\n", err)
	}

	exec.Command("net", "start", "rustdesk").Run()
	time.Sleep(3 * time.Second)

	// 4. Show ID
	fmt.Println("[4/4] Obteniendo ID de conexión...")
	
	// Open GUI
	exec.Command(rustDeskPath).Start()

	// Get ID via CLI
	out, err := exec.Command(rustDeskPath, "--get-id").Output()
	id := strings.TrimSpace(string(out))

	fmt.Println("\n==========================================")
	fmt.Println("    ✅ INSTALACIÓN COMPLETADA")
	fmt.Println("==========================================")
	fmt.Println("")
	if err == nil && id != "" {
		fmt.Println("    TU ID DE CONEXIÓN ES:")
		fmt.Println("")
		fmt.Printf("    👉  %s\n", id)
	} else {
		fmt.Println("    (Revisa la ventana que se abrió para ver tu ID)")
	}
	fmt.Println("")
	fmt.Println("==========================================")
	
	pause()
}

func findRustDesk() string {
	opts := []string{
		`C:\Program Files\RustDesk\rustdesk.exe`,
		`C:\Program Files (x86)\RustDesk\rustdesk.exe`,
	}
	for _, p := range opts {
		if _, err := os.Stat(p); err == nil {
			return p
		}
	}
	return ""
}

func downloadFile(filepath string, url string) error {
	resp, err := http.Get(url)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	out, err := os.Create(filepath)
	if err != nil {
		return err
	}
	defer out.Close()

	_, err = io.Copy(out, resp.Body)
	return err
}

func pause() {
	fmt.Println("\nPresiona Enter para salir...")
	fmt.Scanln()
}
