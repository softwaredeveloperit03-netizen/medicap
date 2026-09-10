import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StockapprovalComponent } from './stockapproval.component';

describe('StockapprovalComponent', () => {
  let component: StockapprovalComponent;
  let fixture: ComponentFixture<StockapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StockapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StockapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
