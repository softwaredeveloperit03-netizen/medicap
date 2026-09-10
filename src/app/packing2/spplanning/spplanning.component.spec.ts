import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpplanningComponent } from './spplanning.component';

describe('SpplanningComponent', () => {
  let component: SpplanningComponent;
  let fixture: ComponentFixture<SpplanningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpplanningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpplanningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
