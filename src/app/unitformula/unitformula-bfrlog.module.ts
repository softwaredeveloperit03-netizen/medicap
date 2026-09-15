import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { BfrlogComponent } from './bfrlog/bfrlog.component';

const routes: Routes = [{ path: '', component: BfrlogComponent }];

@NgModule({
  declarations: [BfrlogComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class UnitformulaBfrlogModule {}
