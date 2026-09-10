import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckingDedustingMaterials();
  }

  getCheckingDedustingMaterials() {
    this.service.get('store/raw.php?type=getCheckingDedustingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  updateMaterial(status) {
    let details = this.selectedReport['dedusting_details'];
    this.service.post('store/raw.php?type=updateDedustingMaterial&status=' + status + '&id=' + this.selectedReport['id'], JSON.stringify(details)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Material updated successfully');
        this.isView = false;
        this.getCheckingDedustingMaterials();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
