import { Component, Input } from '@angular/core';
import { HPLC_BASE, HPLC_DEMO_MODE } from './hplc-demo-mode';
import { HplcDemoDataService } from './hplc-demo-data.service';

@Component({
  selector: 'app-hplc-page-shell',
  templateUrl: './hplc-page-shell.component.html',
  styleUrls: ['./hplc-shared.css'],
})
export class HplcPageShellComponent {
  @Input() title = 'HPLC';
  @Input() subtitle = '';
  @Input() backLink = HPLC_BASE;
  @Input() showDemo = true;

  demoMode = HPLC_DEMO_MODE;
  demoBanner = '';

  constructor(public demo: HplcDemoDataService) {
    this.demoBanner = demo.getBanner();
  }
}
