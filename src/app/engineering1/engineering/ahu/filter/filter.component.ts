import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-filter',
  templateUrl: './filter.component.html',
  styleUrls: ['./filter.component.css']
})
export class FilterComponent implements OnInit {

  selectedFile;
  equipment_type = '';
  filter_type = '';
  departments: any[] = [];
  enggEmployees: any[] = [];

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDepartments();
    this.getEngineeringEmployees();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  getEngineeringEmployees() {
    // Same source as breakdown/maintenance forms (plant Active employees)
    this.service.get('engineering/maintenance.php?type=getEmployee').subscribe((response) => {
      const list = Array.isArray(response) ? response : [];
      const engg = list.filter((emp) =>
        String(emp.department || '')
          .toLowerCase()
          .replace(/\s+/g, ' ')
          .trim()
          .includes('engineering')
      );
      this.enggEmployees = this.mapEmployees(engg.length ? engg : list);
    });
  }

  private mapEmployees(list: any[]) {
    return list
      .map((emp) => {
        const name = [emp.firstname, emp.middlename, emp.lastname]
          .filter((p) => !!p && String(p).trim() !== '')
          .join(' ')
          .trim();
        const display = name
          ? name + (emp.emp_id ? ' (' + emp.emp_id + ')' : '')
          : emp.emp_id || emp.emp_name || '';
        return { ...emp, display_name: display };
      })
      .filter((emp) => !!emp.display_name);
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('engineering/cleaning.php?type=savefliter_cleaning_form', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.resetForm();
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
