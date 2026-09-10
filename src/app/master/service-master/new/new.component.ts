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


  service_type='Annual Maintenance';
  entryBy = '';
  entryDate = '';

  gsts;
  departments: Object;


  types = [
  'Annual Maintenance', 
  'Equipment Service', 
  'Breakdown Maintenance', 
  'Manpower Service',
  'Training Service',
  'Consultancy Service',
  'Development Service',
  'Transport Service',
  'Contracts Service',
  'Other Service',
  ];


  typeField='';
  isEdit = false;
  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void { 
    this.entryBy =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('employee_name') ||
      localStorage.getItem('emp_id') ||
      '';
    this.entryDate = new Date().toISOString().slice(0, 10);
    this.getDepartment();
    this.getGst();
  }


  getGst(){
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gsts = response;
    });
  }


  editType(val){
    if(val=='ADD NEW'){
      this.isEdit = true;
    }
  }

  newType(val){
    this.types.push(val.value.typeField);
    this.service_type = val.value.typeField;
    this.isEdit =false;
  }

  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    })
  }

  
  addService(data) {
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    let temp= data.value;
    temp['entry_by'] = this.entryBy;
    temp['entry_date'] = this.entryDate;
    this.service.post('master/service.php?type=saveService', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
        this.router.navigate(['/master/service-master']);
      } else {
        alertify.error(response['status']);
      }
    });
  }
}
