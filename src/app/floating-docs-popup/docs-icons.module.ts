import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ClarityModule } from '@clr/angular';
import { RouterModule } from '@angular/router';
import { FloatingDocsPopupComponent } from './floating-docs-popup.component';
import { TranslateModule } from '@ngx-translate/core';
import { SidebarGuideLinkComponent } from '../shared/department-guide/sidebar-guide-link.component';

@NgModule({
  declarations: [FloatingDocsPopupComponent, SidebarGuideLinkComponent],
  imports: [TranslateModule, CommonModule, ClarityModule, RouterModule],
  exports: [FloatingDocsPopupComponent, SidebarGuideLinkComponent],
})
export class DocsIconsModule {}
