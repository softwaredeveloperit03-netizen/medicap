import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-closing',
  templateUrl: './closing.component.html',
  styleUrls: ['./closing.component.css']
})
export class ClosingComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRecommendations();
  }

  getPendingRecommendations(){
    this.service.get('qms/deviation.php?type=getPendingClosing').subscribe(response=>{
      this.results=response;
    });
  }

  viewfile(link){
    window.open(this.service.url + 'upload/deviation/' + link);
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
    console.log(this.selectedResult);
  }
 
  save(){
    let temp={};
    temp['deviation_no']=this.selectedResult['deviation_no'];
     this.service.post('qms/deviation.php?type=saveClosing&id=' +this.selectedResult['id']+'&challan_no='+this.selectedResult['challan_no'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Save Successfully!');
        this.isView = false;
        this.getPendingRecommendations();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }
}
