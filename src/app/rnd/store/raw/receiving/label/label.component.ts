import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-label',
  templateUrl: './label.component.html',
  styleUrls: ['./label.component.css']
})
export class LabelComponent implements OnInit {
  results;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getReceivingLog();
  }

  getReceivingLog() {
    this.service.get('store/raw.php?type=getPendingReceivingLables').subscribe(response => {
      this.results = response;
    });
  }
  printLabel(id){
    this.service.open('pdf1/labels.php?type=receivingLabels&id='+id);
  }
}
