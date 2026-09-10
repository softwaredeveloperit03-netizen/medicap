import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ClarityModule } from '@clr/angular';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { EditorModule } from '@tinymce/tinymce-angular';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'log', component: LogComponent },
];

@NgModule({
  declarations: [LogComponent, NewComponent, DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    FormsModule,
    CommonModule,
    ReactiveFormsModule,
    ClarityModule,
    EditorModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingListModule { }
