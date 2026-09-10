import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingStationaries();
  }

  getPendingStationaries(){
    this.service.get('master/stationary.php?type=getPendingStationaries').subscribe(response=>{
      this.results=response;
    })


  }

  update(status,id) {
    this.service.get('master/stationary.php?type=updateStationary&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.getPendingStationaries();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
