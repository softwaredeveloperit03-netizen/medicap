import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getAwaitingReceivingRawLabels();
  }

  getAwaitingReceivingRawLabels() {
    this.service.get('store/label.php?type=getAwaitingReceivingRawLabels').subscribe(response => {
      this.results = response;
    });
  }

  print(data) {
    console.log(data);
    this.service.open('store/label.php?type=printReceivingLabel&id=' + data['id'] + '&batch_no=' + data['batch_no']);
  }

}
