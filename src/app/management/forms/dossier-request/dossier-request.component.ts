import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dossier-request',
  templateUrl: './dossier-request.component.html'
})
export class DossierRequestComponent implements OnInit {
  datalist = [
    {"product_name":"Product 1","batch_no":"BATCH01","batch_size":"10","mfg_date":"2020/05/03","completion_date":"2020/10/03","self_life":"Self Life","yield_percent":"90 %"},
    {"product_name":"Product 2","batch_no":"BATCH02","batch_size":"10","mfg_date":"2020/05/03","completion_date":"2020/10/03","self_life":"Self Life","yield_percent":"90 %"}
  ];
  constructor() { }

  ngOnInit() {
  }

}
