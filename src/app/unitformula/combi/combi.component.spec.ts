import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CombiComponent } from './combi.component';

describe('CombiComponent', () => {
  let component: CombiComponent;
  let fixture: ComponentFixture<CombiComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CombiComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CombiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
