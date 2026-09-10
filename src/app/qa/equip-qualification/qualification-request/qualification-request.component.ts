import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-qualification-request',
  templateUrl: './qualification-request.component.html',
  styleUrls: ['./qualification-request.component.css']
})
export class QualificationRequestComponent implements OnInit {

  isNew = false;
  dept;
  results;
  selectedSection=[];
    router: any;
    equipments;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getDepartment();
    this.getData();
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.dept=response;
    });
  }

  getData(){
    this.service.get('qa/qualification.php?type=getQualificationRequest').subscribe(response=>{
      this.results=response;
    });
  }

  selectedEquip =[];
  selectEquipment(index){
    this.selectedEquip= this.equipments[index-1];
  }

  getSection(value){
    this.service.get('qa/qualification.php?type=getEquipmentBydept&deptName='+value).subscribe(response=>{
      this.equipments=response;
    });
  } 

  saveRequest(data){
    this.service.post('qa/qualification.php?type=saveQualificationRequest',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Save data successfuly');
        data.resetForm();
        this.getData();
        this.isNew = false;
        
      }else("Some Error Occured");
    });
  }



}
