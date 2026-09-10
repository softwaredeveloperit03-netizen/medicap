import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MicrobiologicalComponent } from './microbiological.component';

describe('MicrobiologicalComponent', () => {
  let component: MicrobiologicalComponent;
  let fixture: ComponentFixture<MicrobiologicalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MicrobiologicalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MicrobiologicalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
