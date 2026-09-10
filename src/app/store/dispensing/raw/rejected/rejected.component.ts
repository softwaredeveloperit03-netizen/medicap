import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-rejected',
  templateUrl: './rejected.component.html',
  styleUrls: ['./rejected.component.css']
})
export class RejectedComponent implements OnInit {
  isView = false;
  results;
  remark='';
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getDispensingActivities();
  }

  getDispensingActivities(){
    this.service.get('store/dispensing.php?type=getRejectedRequests').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.isView = true;
  }

  save(){
    this.service.get('store/dispensing.php?type=saveRequest&id='+this.selectedResult['id'] +'&remark='+this.remark).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.getDispensingActivities();
        this.isView=false;
      }else{
        alertify.error('some error occured!');
      }
    });
  }


}
