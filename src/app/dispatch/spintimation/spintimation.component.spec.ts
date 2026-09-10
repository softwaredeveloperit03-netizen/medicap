import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpintimationComponent } from './spintimation.component';

describe('SpintimationComponent', () => {
  let component: SpintimationComponent;
  let fixture: ComponentFixture<SpintimationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpintimationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpintimationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
