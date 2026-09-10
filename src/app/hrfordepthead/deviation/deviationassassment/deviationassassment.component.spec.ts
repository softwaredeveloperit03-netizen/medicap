import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeviationassassmentComponent } from './deviationassassment.component';

describe('DeviationassassmentComponent', () => {
  let component: DeviationassassmentComponent;
  let fixture: ComponentFixture<DeviationassassmentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeviationassassmentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeviationassassmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
