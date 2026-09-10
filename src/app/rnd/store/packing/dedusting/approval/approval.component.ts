import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessDedustings();
  }

  getInprocessDedustings() {
    this.service.get('store/packing.php?type=getInprocessDedustings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    let details = this.selectedReport['dedusting_details'];
    this.service.post('store/packing.php?type=updateDedustingMaterial&status=' + status + '&id=' + this.selectedReport['id'], JSON.stringify(details)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.error('Material updated successfully');
        this.isView = false;
        this.getInprocessDedustings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
