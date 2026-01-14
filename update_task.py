#!/usr/bin/env python3
"""
Script para actualizar el estado de las tareas en el archivo TODOS.md.

Uso:
    python update_task.py --task "Descripción de la tarea" --status "completada|pendiente|en_progreso"
"""

import argparse
import re


def update_task_status(task_description, new_status):
    """
    Actualiza el estado de una tarea en el archivo TODOS.md.
    
    Args:
        task_description (str): Descripción de la tarea a actualizar.
        new_status (str): Nuevo estado de la tarea (completada, pendiente, en_progreso).
    """
    status_map = {
        "completada": "x",
        "pendiente": " ",
        "en_progreso": "-"
    }
    
    if new_status not in status_map:
        print(f"Error: Estado no válido. Usa uno de: {list(status_map.keys())}")
        return
    
    new_marker = status_map[new_status]
    
    try:
        with open("TODOS.md", "r", encoding="utf-8") as file:
            content = file.read()
        
        # Buscar la tarea y actualizar su estado
        pattern = re.compile(rf"\- \[.] {re.escape(task_description)}", re.IGNORECASE)
        new_content = pattern.sub(f"- [{new_marker}] {task_description}", content)
        
        if new_content == content:
            print(f"Error: No se encontró la tarea '{task_description}' en TODOS.md")
            return
        
        with open("TODOS.md", "w", encoding="utf-8") as file:
            file.write(new_content)
        
        print(f"Tarea '{task_description}' actualizada a '{new_status}'")
    except FileNotFoundError:
        print("Error: No se encontró el archivo TODOS.md")
    except Exception as e:
        print(f"Error inesperado: {e}")


def main():
    parser = argparse.ArgumentParser(description="Actualizar el estado de una tarea en TODOS.md")
    parser.add_argument("--task", required=True, help="Descripción de la tarea")
    parser.add_argument("--status", required=True, choices=["completada", "pendiente", "en_progreso"], help="Nuevo estado de la tarea")
    
    args = parser.parse_args()
    update_task_status(args.task, args.status)


if __name__ == "__main__":
    main()