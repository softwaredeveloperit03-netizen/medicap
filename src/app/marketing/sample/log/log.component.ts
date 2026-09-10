import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
 

  constructor(private service: DataAccessService)  { }

  ngOnInit() {
     this.getSamplelist();
   }

 selectedResult =[];
 isView=false;
  view(entry: any){
    this.selectedResult = entry
    this.isView=true;
  } 
 
 
  sampleList;
  getSamplelist() {
    this.service.get('marketing/sample.php?type=getSamplesLog').subscribe(response => {
      this.sampleList = response;
    });
  }

  getStatusClass(status: string): string {
    switch (status) {
      case 'Issued':
        return 'label-success';
      case 'Rejected':
        return 'label-danger';
      case 'Pending':
      default:
        return 'label-warning';
    }
  }


   
  
  


 


   

}
