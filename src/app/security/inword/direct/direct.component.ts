import { Component } from '@angular/core';

@Component({
  selector: 'app-direct',
  templateUrl: './direct.component.html',
  styleUrls: ['./direct.component.css']
})
export class DirectComponent {
  activeView: 'new' | 'approval' = 'new';

  setView(view: 'new' | 'approval') {
    this.activeView = view;
  }
}
