import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results=[];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  this.getPendingIndicators();
  }
  
  getPendingIndicators(){
    this.service.get('qc/indicator.php?type=getPendingIndicators').subscribe((response:any)=>{
      this.results=response;
    })
  }

  action(id,status){
    this.service.get('qc/indicator.php?type=updateIndicator&id='+id+'&status='+status).subscribe((response:any)=>{
      if(response['status']=='success'){
        alertify.success("updated succesfully")
        this.service.get('qc/indicator.php?type=getPendingIndicators').subscribe((response:any)=>{
          this.results=response;
        })
      }
      else{
        alertify.error(" record not updated")
      }
    })

  }

}
