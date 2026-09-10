import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  employeeForm: FormGroup;
  pro: boolean = false;
  employees: any;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
 
    this.getEmployees();
  
  }
  isView=false;
  selectedResult=[];
 view(index){
  this.selectedResult=this.employees[index]
  this.isView=true;
 }
prime='';
second='';
qms='';
 Add(){
  let temp={}
  temp['responsibility']=this.prime;
  if (!this.selectedResult['Primary_responsibilities']) {
    this.selectedResult['Primary_responsibilities'] = [];
  }
  this.selectedResult['Primary_responsibilities'].push(temp);
  this.prime='';
 }
 DEL(index){
  this.selectedResult['Primary_responsibilities'].splice(index,1)
 }
 Add2(){
  let temp={}
  temp['responsibility']=this.second;
  if (!this.selectedResult['Secondary_responsibilities']) {
    this.selectedResult['Secondary_responsibilities'] = [];
  }
  this.selectedResult['Secondary_responsibilities'].push(temp);
  this.second='';
 }
 DEL2(index){
  this.selectedResult['Secondary_responsibilities'].splice(index,1)
 }
 Add3(){
  let temp={}
  temp['responsibility']=this.qms;
  if (!this.selectedResult['QMS_responsibilities']) {
    this.selectedResult['QMS_responsibilities'] = [];
  }
  this.selectedResult['QMS_responsibilities'].push(temp);
  this.qms='';
 }
 DEL3(index){
  this.selectedResult['QMS_responsibilities'].splice(index,1)
 }
  onSubmit() {
    
    let temp = this.selectedResult;
    this.service.post('qa/job.php?type=savejob_responsibilities', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getEmployees() {
    this.service.get('qa/job.php?type=jobseremployee').subscribe(response => {
      this.employees = response;
      console.log('this.employees',this.employees);
    })
  }

}
