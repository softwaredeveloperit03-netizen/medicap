import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-opostapproval1',
  templateUrl: './opostapproval1.component.html',
  styleUrls: ['./opostapproval1.component.css']
})
export class Opostapproval1Component implements OnInit {
  Action_plan;
  results;
  isView=false;
  selectedResult=[];
  plant_id;
  constructor(private service:DataAccessService) { }
  departments1;
  ngOnInit(): void {
    this.getInitiatedCC();
    this.getDepartments();
    // this.service.observableDepartment.subscribe(response => {
    //   this.departments1 = response;
    // });
    this.plant_id = this.service.getPlantConfigFields("plant_id");
  }

  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingPostApprovalbyCQA').subscribe(response=>{
      this.results=response;
    });
  }
  employees
  getEmployees(value) {
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments1 = response;
    });
    
  }comment;
  selectedRegulatory:any=[];
  change_reg_doss;
change_pharma;
change_update;
change_cust_req;
change_regulatory_req;
dic_req;
  view(index){
    this.selectedResult=this.results[index];
    this.selectedRegulatory=this.selectedResult['regulatory'][0];
    this.comment=this.selectedRegulatory['comment'];
    this.change_reg_doss=this.selectedRegulatory['change_reg_doss']
    this.change_pharma=this.selectedRegulatory['change_pharma']
this.change_update=this.selectedRegulatory['change_update']
this.change_cust_req=this.selectedRegulatory['change_cust_req']
this.change_regulatory_req=this.selectedRegulatory['change_regulatory_req']
this.dic_req=this.selectedRegulatory['dic_req']
    this.isView=true;
    console.log(this.departments1);
  }
  List=[];
  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    
    this.List[this.List.length] = temp;
    console.log(this.List)
    data.resetForm();
  }
  viewfile(link) {
    window.open(this.service.url + 'upload/ccpermanant/' + link);
  }

  save(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp=data.value;
    temp['final_qa_action']=this.List;
    temp['cc_no']=this.selectedResult['cc_no'];
    temp['requirement']=this.List;
    temp['Action_plan']=this.Action_plan;
    this.service.post('qms/ccpermanant.php?type=savePostApprovalChecking'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }

}
