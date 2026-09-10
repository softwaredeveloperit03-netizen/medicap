import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PaintWallComponent } from './paint-wall.component';

describe('PaintWallComponent', () => {
  let component: PaintWallComponent;
  let fixture: ComponentFixture<PaintWallComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PaintWallComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PaintWallComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
