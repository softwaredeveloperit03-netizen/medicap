
import { Component, OnInit } from '@angular/core';

import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-batchplan',
  templateUrl: './batchplan.component.html',
  styleUrls: ['./batchplan.component.css']
})
export class BatchplanComponent implements OnInit {allocate_batch_no='Yes';

constructor(private service: DataAccessService) {
  
}
results;
ngOnInit() {
   this.getPlans();
}

getPlans() {

  this.service.get('production/plan.php?type=getbatchPlansForPacking').subscribe(response => {
    this.results = response;

  });
}

}
