import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-opreapproval',
  templateUrl: './opreapproval.component.html',
  styleUrls: ['./opreapproval.component.css']
})
export class OpreapprovalComponent implements OnInit {
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
  departments = [
    { name: 'Production', status: false},
    { name: 'Quality Control', status: false},
    { name: 'Engineering/Project', status: false},
    { name: 'WareHouse', status: false},
    { name: 'EHS', status: false},
    { name: 'HR', status: false},
    { name: 'Regulatory Affairs', status: false},
    { name: 'R&D', status: false},
    { name: 'Purchase', status: false},
    { name: 'Marketing', status: false},
    { name: 'IT', status: false},
    { name: 'AQA', status: false},
    { name: 'Any Other', status: false}
  ];
  employees
  getEmployees(value) {
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }
  selectedFile2: File;


  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  
  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingPreApproval').subscribe(response=>{
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
  updateDept(value, i) {
    this.departments[i].status = value;
  }
  save(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    const uploadData = new FormData();
    if (this.selectedFile2 !== undefined) {
      uploadData.append('photo', this.selectedFile2, this.selectedFile2.name);
    }
    let temp=data.value;
    temp['cc_no']=this.selectedResult['cc_no'];
    temp['requirement']=this.List;
    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['name'];
      }
    }
    temp['departments'] = test;
    uploadData.append('data', JSON.stringify(temp));
    this.service.post('qms/ccpermanant.php?type=savePreApprovalCC'+'&id='+this.selectedResult['id'],uploadData).subscribe(response => {
    // this.service.post('qms/ccpermanant.php?type=savePreApprovalCC'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
