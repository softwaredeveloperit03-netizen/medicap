   import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  remark='';

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingRequests();
  }

  getPendingRequests(){
    this.service.get('ipqc/etp.php?type=getPendingRequests').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  save(){
    let temp=this.selectedResult;
    temp['remark']=this.remark;
    this.service.post('ipqc/etp.php?type=saveTestingRequest&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alert('data save successfuly');
        this.isView=false;
        this.getPendingRequests();
      }else{
        alert('Some error Occured!');
      }
    });

  }

}
