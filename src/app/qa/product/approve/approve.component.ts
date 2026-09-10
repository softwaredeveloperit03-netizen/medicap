import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {

  isView = false;
  results;
  selectedData=[];
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingProducts();
  }

  getPendingProducts() {
    this.service.get('qa/product.php?type=getPendingProducts').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  open(file) {
    if (file !== '') {
      window.open(this.service.url + 'upload/product/' + file);
    } else {
      alert('File not available');
    }
  }

  update(status) {
    this.service.get('qa/product.php?type=approveProduct&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record updated successfully');
        this.isView = false;
        this.getPendingProducts();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
