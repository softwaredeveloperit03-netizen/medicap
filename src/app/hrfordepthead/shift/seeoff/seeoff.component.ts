import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-seeoff',
  templateUrl: './seeoff.component.html',
  styleUrls: ['./seeoff.component.css']
})
export class SeeoffComponent implements OnInit {
  department;
  result;
  shift;
  list=[];
  shifts;
  

  max_date = '';
  from_date = '';
  to_date = '';
    departments;
    selectedDepartmentData;
  constructor(private service:DataAccessService, private router: Router, private datePipe: DatePipe) {
    let date = new Date();
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.from_date = this.max_date;
  }

  ngOnInit() {
   
    this.getShiftList()
    this.getDepartments();
    this.selectedDepartmentData = this.service.getPlantConfigFields("selectedDepartmentData")
  }
  getEmployees(value){

    

    this.service.get('hr/shift.php?type=getEmployees&department1='+value).subscribe(response=>{
      this.result=response;
    })



  }
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
 
  getShiftList(){
    this.service.get('hr/shift.php?type=getShiftList').subscribe(response=>{
      this.shifts=response;
    })
  }

  save(data) {
    if (!data.valid) {
      alertify.error('Please select Shift');
      return;
    }
    this.service.post('hr/shift.php?type=save_shiftAllocate&from_date=' + this.from_date + '&to_date=' + this.to_date,JSON.stringify(this.result)).subscribe(response=>{
      if (response['status'] == 'success') {
        alertify.success('Records saved successfully');
        this.router.navigate(['/hr/shift']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  weekly_off = '';
 

  addWeeklyOff(){
    for(let i = 0; i<this.result.length; i++){
      this.result[i].weekly_off = this.weekly_off;
    }
  }
  addShift(){
    for(let i = 0; i<this.result.length; i++){
      this.result[i].shift = this.shift;
    }
  }

 





}
