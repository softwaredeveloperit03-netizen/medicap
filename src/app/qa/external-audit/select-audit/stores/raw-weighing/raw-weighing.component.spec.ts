import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawWeighingComponent } from './raw-weighing.component';

describe('RawWeighingComponent', () => {
  let component: RawWeighingComponent;
  let fixture: ComponentFixture<RawWeighingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawWeighingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RawWeighingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
