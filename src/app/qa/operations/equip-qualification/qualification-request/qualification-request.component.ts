import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.service.get('qa/qualification_iq.php?type=getRequest').subscribe(response=>{
      this.results=response;
    });
  }

  getSection(index){
    index=index-1;
    if(index != -1){
      // let AllData=this.dept['sections'];
      // this.selectedSection=AllData[index]
     this.selectedSection=this.dept[index]
    }
  
  }

  saveRequest(data){
    this.service.post('qa/qualification_iq.php?type=saveQualificationRequest',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alert('Save data successfuly');
        data.resetForm();
      }else("Some Error Occured");
    });
  }



}
