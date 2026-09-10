import { Component } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  plant_type = '';

  constructor(private service: DataAccessService) {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  get cards(): QcDeptCard[] {
    const cards: QcDeptCard[] = [
      { id: 'start', title: 'Start Activity', route: 'start', icon: 'fa-play', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    ];
    if (this.plant_type !== 'Formulation') {
      cards.push({ id: 'inprocess', title: 'Inprocess Activity', route: 'inprocess', icon: 'fa-hourglass-half', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' });
    }
    if (this.plant_type === 'Formulation') {
      cards.push({ id: 'inprocess-formulation', title: 'Inprocess Activity.', route: 'inprocess-formulation', icon: 'fa-hourglass-half', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' });
    }
    cards.push({ id: 'complete', title: 'Complete Activity', route: 'complete', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' });
    return cards;
  }
}
