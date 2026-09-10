import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-chromatogram-interpretation',
  templateUrl: './chromatogram-interpretation.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class ChromatogramInterpretationComponent implements OnInit {
  back = HPLC_BASE;
  data: any;

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.data = this.demo.getInterpretation();
  }
}
