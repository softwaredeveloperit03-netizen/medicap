import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-pending',
  templateUrl: './pending.component.html',
  styleUrls: ['./pending.component.css']
})
export class PendingComponent implements OnInit {

  results;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getSamplings();
  }

  getSamplings() {
    this.service.get('qc/sampling/packing.php?type=getPendingLabels').subscribe(response => {
      this.results = response;
    });
  }

  printLabel(id){
    this.service.open('pdf1/labels.php?type=samplingLabels&id='+id);
  }

}
