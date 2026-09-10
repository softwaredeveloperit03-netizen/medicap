import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  isView = false;
  results = [];
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.results = [
      {"id":"1","product_code":"PR001","product_name":"Demo Product","grade":"BP","batch_no":"1234","batch_size":"1","packing_qty":"10"}
    ];
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  save() {
    this.service.post('sops.php?type=prepareSOP', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('SOP Created Successfully');
      } else {
        alert('Failed: An error occured, please try again!');
      }
       this.isView = false;
    });
  }

  close(){
    this.isView = false;
  }

}
