import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExtededComponent } from './exteded.component';

describe('ExtededComponent', () => {
  let component: ExtededComponent;
  let fixture: ComponentFixture<ExtededComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExtededComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExtededComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
