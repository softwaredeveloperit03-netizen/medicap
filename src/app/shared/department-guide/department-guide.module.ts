import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { DepartmentGuideComponent } from './department-guide.component';

const routes: Routes = [{ path: '', component: DepartmentGuideComponent }];

@NgModule({
  declarations: [DepartmentGuideComponent],
  imports: [CommonModule, FormsModule, RouterModule.forChild(routes)],
  exports: [DepartmentGuideComponent],
})
export class DepartmentGuideModule {}
