import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PreapprovalComponent } from './preapproval.component';

describe('PreapprovalComponent', () => {
  let component: PreapprovalComponent;
  let fixture: ComponentFixture<PreapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PreapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PreapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
