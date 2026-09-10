import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import {DashboardComponent} from './dashboard/dashboard.component';
import { RequestComponent } from './request/request.component';
import { AreaComponent } from './area/area.component';
import { SampleComponent } from './sample/sample.component';
import { StartsampleComponent } from './startsample/startsample.component';
import { DocsIconsModule } from '../../../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'area', component: AreaComponent},
  { path: 'sample', component: SampleComponent },
  { path: 'Startsample', component: StartsampleComponent },

]

@NgModule({
  declarations: [DashboardComponent, RequestComponent,AreaComponent,SampleComponent, StartsampleComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class SamplingModule { }
