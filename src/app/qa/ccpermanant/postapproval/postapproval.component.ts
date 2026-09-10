import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-postapproval',
  templateUrl: './postapproval.component.html',
  styleUrls: ['./postapproval.component.css']
})
export class PostapprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  plant_id;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }

  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingPostApproval').subscribe(response=>{
      this.results=response;
    });
  }
  selectedRegulatory:any=[];
  selectedRegulatory1:any=[];
  comment;
  view(index){
    this.selectedResult=this.results[index];
    this.selectedRegulatory=this.selectedResult['regulatory'][0];
    this.selectedRegulatory1=this.selectedResult['regulatory']['change_regulatory_req'];
    this.isView=true;
    this.comment=this.selectedRegulatory['comment'];
    console.log(this.selectedRegulatory);
    console.log(this.comment);
    console.log(this.selectedRegulatory1);
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
    this.service.post('qms/ccpermanant.php?type=savePostApproval'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
