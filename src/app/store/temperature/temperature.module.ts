import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { TemperatureComponent } from './temperature.component';

@NgModule({
  declarations: [TemperatureComponent],
  imports: [CommonModule, FormsModule, ClarityModule, RouterModule, TranslateModule],
  exports: [TemperatureComponent],
})
export class TemperatureModule {}
