import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PackingWeighingComponent } from './packing-weighing.component';

describe('PackingWeighingComponent', () => {
  let component: PackingWeighingComponent;
  let fixture: ComponentFixture<PackingWeighingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PackingWeighingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PackingWeighingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
