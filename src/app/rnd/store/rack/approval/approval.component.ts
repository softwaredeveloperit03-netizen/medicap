import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results;
  isView = false;
  sections;
  units;

  selectedRack = []; 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingRacks();
   
  }

  getPendingRacks() {
    this.service.get('store/location.php?type=getPendingRacks').subscribe(response => {
      this.results = response;
    });
  }

   view(index) {
    this. selectedRack  = this.results[index];
    this.isView = true;
  }

  updateRack(status) {
    this.service.get('store/location.php?type=updateRack&status=' + status + '&id=' + this.selectedRack['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Rack updated successfully');
        this.isView = false;
        this.getPendingRacks();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
