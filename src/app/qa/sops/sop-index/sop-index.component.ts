import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sop-index',
  templateUrl: './sop-index.component.html',
  styleUrls: ['./sop-index.component.css']
})
export class SopIndexComponent implements OnInit {

  sops = [];
  sopsData = [];
  departments = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getFormDetails(): void {
    this.service.get('sops.php?type=getSOPIndex').subscribe(response => {
      this.sopsData = JSON.parse(JSON.stringify(response));
      if (this.departments.length > 0) {
        this.onDepartmentChange('');
      }
    });
  }

  getDepartments(): void {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = JSON.parse(JSON.stringify(response));
      this.getFormDetails();
    });
  }

  onDepartmentChange(department): void {
    if (department == '') {
      if (this.departments.length > 0) {
        department = this.departments[0].department_name;
      }
    }
    this.sops = [];
    this.sopsData.forEach(element => {
      if (element.department == department) {
        this.sops.push(element);
      }
    });

  }

  viewSOP(item) {
    if (item.sop_type == 'Uploaded') {
      if (item.sop_file !== '' && item.sop_file !== undefined && item.sop_file !== null) {
        const url = this.service.url + 'upload/' + item.sop_file;
        window.open(url, '_blank');
      }
    } else if (item.sop_type == 'Created') {
      if (item.file !== '' && item.file !== undefined && item.file !== null) {
        const url = this.service.url + 'sops/' + item.file;
        window.open(url, '_blank');
      }
    }
  }

}
