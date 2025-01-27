import React from "react";

import PortalContent from "./components/modal";
import { createRoot } from "react-dom";

// Listen to button clicks and log the image ID
document.addEventListener("click", function (event) {
  if (event.target.classList.contains("wp_target_crop_focal_point")) {
    // Get the image ID from the button ID
    const button = event.target;
    const imageId = button.getAttribute("imageId");

    if (imageId) {
      // Create a Portal and append it to the body and render
      event.preventDefault();

      // Create a container for the portal
      const portalContainer = document.createElement("div");
      portalContainer.id = "custom-portal-container";
      document.body.appendChild(portalContainer);

      // Render the portal content
      const root = createRoot(portalContainer);
      root.render(
        <PortalContent
          onClose={() => {
            // Unmount and remove the portal
            root.unmount();
            document.body.removeChild(portalContainer);
          }}
          mediaId={imageId}
        />
      );
    }
  }
});
