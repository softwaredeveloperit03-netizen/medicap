import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-action',
  templateUrl: './action.component.html',
  styleUrls: ['./action.component.css']
})
export class ActionComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];

  remark='';
  isrejected=false;
  isapprove=false;
  constructor(private service:DataAccessService) { }
  departments1;
  ngOnInit(): void {
    this.getInitiatedCC();
    this.service.observableDepartment.subscribe(response => {
      this.departments1 = response;
    });
  }
  employees
  getEmployees(value) {
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }


  
  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingaction&department1=Purchase').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
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
     

  getChanges(value){
    if(value=='REJECTED' || value=='CANCELLED'){
      this.isrejected=true;
      this.isapprove=false;
    }else{
      this.isrejected=false;
      this.isapprove=true;
    }
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
    temp['cc_no']=this.selectedResult['cc_no'];
    temp['requirement']=this.List;
    this.service.post('qms/ccpermanant.php?type=savePreApprovalCC'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
