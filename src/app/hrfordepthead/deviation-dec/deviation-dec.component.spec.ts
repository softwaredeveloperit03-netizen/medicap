import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeviationDecComponent } from './deviation-dec.component';

describe('DeviationDecComponent', () => {
  let component: DeviationDecComponent;
  let fixture: ComponentFixture<DeviationDecComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeviationDecComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeviationDecComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
