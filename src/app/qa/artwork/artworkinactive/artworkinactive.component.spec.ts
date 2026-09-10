import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ArtworkinactiveComponent } from './artworkinactive.component';

describe('ArtworkinactiveComponent', () => {
  let component: ArtworkinactiveComponent;
  let fixture: ComponentFixture<ArtworkinactiveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ArtworkinactiveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ArtworkinactiveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
