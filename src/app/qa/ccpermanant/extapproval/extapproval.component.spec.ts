import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExtapprovalComponent } from './extapproval.component';

describe('ExtapprovalComponent', () => {
  let component: ExtapprovalComponent;
  let fixture: ComponentFixture<ExtapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExtapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExtapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
