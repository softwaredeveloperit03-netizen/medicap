import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
 import { NewComponent } from './new/new.component';
 import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { New2Component } from './new2/new2.component';
import { QuillModule } from 'ngx-quill';
import { EditorModule } from '@tinymce/tinymce-angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'new/:id', component: NewComponent},
  { path: 'news/:id', component: New2Component},
  { path: 'log', component: LogComponent}
];
 
@NgModule({
  declarations: [ NewComponent, New2Component, LogComponent,],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
    QuillModule.forRoot(),
    EditorModule,

  ]
})
export class MethodsModule { }
