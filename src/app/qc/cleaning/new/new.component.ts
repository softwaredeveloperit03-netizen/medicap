import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  today = '';
  before = '';
  date = '';
  clean_to = '';
  glasswares: any[] = [];
  glasswareNames: string[] = [];
  filteredGlasswares: any[] = [];
  operators: any[] = [];
  glassware_name = '';
  glassware_code = '';
  clean_by = '';
  cleaning_type = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.before = this.today;
    this.date = this.today;
    this.clean_to = this.today;
  }

  ngOnInit(): void {
    this.getGlasswares();
    this.getOperators();
  }

  getGlasswares() {
    this.service
      .get('qc/glassware.php?type=getGlasswaresForCleaning')
      .subscribe((response: any) => {
        this.glasswares = Array.isArray(response) ? response : [];
        const names = new Set<string>();
        for (const row of this.glasswares) {
          if (row.name) {
            names.add(row.name);
          }
        }
        this.glasswareNames = Array.from(names).sort();
      });
  }

  getOperators() {
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

  onGlasswareNameChange() {
    this.glassware_code = '';
    this.filteredGlasswares = this.glasswares.filter(
      (row) => row.name === this.glassware_name
    );
  }

  onGlasswareCodeChange() {
    const row = this.glasswares.find(
      (item) =>
        String(item.glassware_no || item.id) === String(this.glassware_code)
    );
    if (row && !this.glassware_name) {
      this.glassware_name = row.name;
      this.onGlasswareNameChange();
    }
  }

  saveCleaning(data: any) {
    const cleaningType = (this.cleaning_type || data?.value?.cleaning_type || '').trim();
    if (!this.glassware_name || !this.glassware_code || !this.date || !this.clean_to || !cleaningType || !this.clean_by) {
      alertify.error('All fields are required');
      return;
    }
    const payload = {
      glassware_name: this.glassware_name,
      glassware_code: this.glassware_code,
      clean_from: this.date,
      clean_to: this.clean_to,
      cleaning_type: cleaningType,
      clean_by: this.clean_by,
    };
    this.service
      .postJson('qc/glassware.php?type=saveGlasswareCleaning', JSON.stringify(payload))
      .subscribe({
        next: (response: any) => {
          const result = typeof response === 'string' ? JSON.parse(response) : response;
          if (result && result['status'] === 'success') {
            alertify.success('Glassware cleaning saved successfully');
            if (data?.resetForm) {
              data.resetForm();
            }
            this.glassware_name = '';
            this.glassware_code = '';
            this.clean_by = '';
            this.cleaning_type = '';
            this.filteredGlasswares = [];
            this.date = this.today;
            this.clean_to = this.today;
            this.router.navigate(['/qc/cleaning']);
          } else {
            alertify.error(result?.['message'] || result?.['status'] || 'Save failed');
          }
        },
        error: () => alertify.error('Save failed'),
      });
  }
}
