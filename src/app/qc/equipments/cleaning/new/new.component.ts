import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  equipments: any[] = [];
  operators: any[] = [];
  selectedResult: any = { equipments: [] };
  clean_by = '';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getEquipments();
    this.getLabours();
  }

  getEquipments() {
    this.service.get('equipments.php?type=getEquipments').subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      this.equipments = this.groupEquipments(rows);
    });
  }

  groupEquipments(rows: any[]): any[] {
    if (!rows.length) {
      return [];
    }
    if (rows[0] && Array.isArray(rows[0].equipments)) {
      return rows;
    }
    const grouped: { [name: string]: any } = {};
    for (const eq of rows) {
      const name = eq.equipment_name || '';
      if (!name) {
        continue;
      }
      if (!grouped[name]) {
        grouped[name] = { equipment_name: name, equipments: [] };
      }
      grouped[name].equipments.push({ ...eq, clean: eq.clean || 'yes' });
    }
    return Object.keys(grouped).map((key) => grouped[key]);
  }

  getLabours() {
    this.service.get('common.php?type=getLabours').subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      if (rows.length) {
        this.operators = rows;
        return;
      }
      this.service.get('common.php?type=get_qc_testing_persons').subscribe((empResp: any) => {
        const employees = Array.isArray(empResp) ? empResp : [];
        this.operators = employees.map((emp: any) => ({
          labour_no: emp.emp_id,
          labour_name: [emp.firstname, emp.middlename, emp.lastname].filter(Boolean).join(' ').trim() || emp.emp_id,
        }));
      });
    });
  }

  onEquipmentNameChange(index: number) {
    if (index > 0 && this.equipments[index - 1]) {
      this.selectedResult = this.equipments[index - 1];
    } else {
      this.selectedResult = { equipments: [] };
    }
  }

  saveCleaning(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .postTextResponse(
        'equipments.php?type=saveGeneralEquipmentCleaning',
        JSON.stringify(data.value)
      )
      .subscribe((text) => {
        const response = this.service.parsePhpJson(text);
        if (response['status'] === 'success') {
          data.resetForm();
          this.router.navigate(['/qc/equipments/cleaning']);
          alertify.success('Equipment cleaning saved successfully');
        } else {
          alertify.error(response['message'] || 'Save failed');
        }
      });
  }
}
