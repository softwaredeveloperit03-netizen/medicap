import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isView= false;
  results;
  selectedResult = [];

  constructor(private service: DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getData();
    this.get_rights();
  }
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  
  getData() {
   
    this.service.get('store/outward.php?type=getoutward').subscribe(response => {
      this.results = response;
    
    });
  }
  materials=[];
  view(index) {
    this.isView = true;
    this.selectedResult = this.results[index] || {};
    this.materials = [];
    try {
      if (Array.isArray(this.selectedResult['materials_parsed'])) {
        this.materials = this.selectedResult['materials_parsed'];
      } else if (typeof this.selectedResult['materials'] === 'string' && this.selectedResult['materials']) {
        const parsed = JSON.parse(this.selectedResult['materials']);
        this.materials = Array.isArray(parsed) ? parsed : [];
      } else if (Array.isArray(this.selectedResult['materials'])) {
        this.materials = this.selectedResult['materials'];
      }
    } catch (e) {
      this.materials = [];
    }
  }

  getUnitDisplay(row: any): string {
    if (row?.unit && row.unit !== 'NA') {
      return row.unit;
    }
    let mats: any[] = [];
    try {
      if (Array.isArray(row?.materials_parsed)) {
        mats = row.materials_parsed;
      } else if (typeof row?.materials === 'string' && row.materials) {
        const parsed = JSON.parse(row.materials);
        mats = Array.isArray(parsed) ? parsed : [];
      } else if (Array.isArray(row?.materials)) {
        mats = row.materials;
      }
    } catch (e) {
      mats = [];
    }
    const units = mats
      .map((m) => (m?.unit != null ? String(m.unit).trim() : ''))
      .filter((u, i, arr) => !!u && arr.indexOf(u) === i);
    return units.length ? units.join(', ') : '—';
  }

  download(){
    this.service.open('store/outward.php?type=downloadOutwordLog');
  }

}
