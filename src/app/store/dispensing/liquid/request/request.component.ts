import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

    isView = false;
    results;
    remark='';
    selectedResult=[];
    constructor(private service:DataAccessService) { }
  
   
    ngOnInit() {
      this.getDispensingActivities();
    }
  
    getDispensingActivities(){
      this.service.get('store/dispensing.php?type=getDispensingRequests').subscribe(response => {
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
  