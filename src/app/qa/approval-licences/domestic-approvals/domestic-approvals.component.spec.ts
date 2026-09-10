import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DomesticApprovalsComponent } from './domestic-approvals.component';

describe('DomesticApprovalsComponent', () => {
  let component: DomesticApprovalsComponent;
  let fixture: ComponentFixture<DomesticApprovalsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DomesticApprovalsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DomesticApprovalsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
