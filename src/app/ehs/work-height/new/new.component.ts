  import { Component, OnInit } from '@angular/core';
  import {DatePipe} from '@angular/common';
  import { DataAccessService } from 'src/app/data-access.service';
   import { Router } from '@angular/router';
  declare let alertify;
  
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]

})
export class NewComponent implements OnInit {
  
    date;
    departments;
    employees;
    plants;
    // contractors;
    constructor(private service: DataAccessService,private datePipe: DatePipe,private router:Router) { 
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  
    ngOnInit() {
      this.getDepartments();
      this.getEmployees();
      this.getPlantName();
      // this.getContractor();
      }
    
/*       getContractor(){
        this.service.get('hr/contractor.php?type=getContractors').subscribe(response=>{
          this.contractors = response;
        })
      } */
      getPlantName(){
        this.service.get('common.php?type=getCompanyUnits').subscribe(response=>{
          this.plants = response;
        })
      }
    getDepartments(){
      this.service.get('common.php?type=getDepartments').subscribe(response=>{
        this.departments = response;
      })
    }
    getEmployees(){
      this.service.get('employee.php?type=getQCPersons').subscribe(response=>{
        this.employees = response;
      })
    }
    saveData(data){
      let temp = data.value;
      this.service.post('ehs/workheight.php?type=saveWorkHeight',JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
         this.router.navigate(['/ehs/work-height'])
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     }); 
    }
  }
  